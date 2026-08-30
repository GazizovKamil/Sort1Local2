<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Company;
use Sort1API\Components\CompanyBalance;
use Sort1API\Components\User;
use Sort1API\Components\Models\Documents;

/**
 * B2B Client Portal Service
 * Handles legal entity registration, finance info, payments, shipments, returns
 */
class B2BClientService extends Model {

    /**
     * 1. Register legal entity
     */
    public static function register_legal_entity($request) {
        $db = DB::getInstance();
        
        // Determine main_company from referer (same as existing register_user)
        preg_match("/https*:\/\/([^\/]+)\/*/", $_SERVER['HTTP_REFERER'], $origin);
        $site = $db->getRow("SELECT company_id, shop_verify_phone, shop_sms_apikey FROM company_sites WHERE site_name=?s", str_replace("www.", "", $origin[1]));
        $main_company_id = (int)($site['company_id'] ?? 0);
        if ($main_company_id <= 0) {
            return self::_error_arr("Не удалось определить основную компанию");
        }

        // Validation
        if (empty($request->contact_name) || empty(trim($request->contact_name))) {
            return self::_error_arr("Укажите контактное лицо");
        }
        if (empty($request->phone) || !preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $request->phone)) {
            return self::_error_arr("Введите телефон в формате +7(XXX)XXX-XX-XX");
        }
        if (empty($request->email) || !preg_match("/[^@]+@[^@]+/", $request->email)) {
            return self::_error_arr("Некорректный email");
        }
        if (empty($request->inn) || !preg_match('/^\d{10}$|^\d{12}$/', $request->inn)) {
            return self::_error_arr("ИНН должен содержать 10 или 12 цифр");
        }
        if (empty($request->captcha) || empty(trim($request->captcha))) {
            return self::_error_arr("Введите ответ на вопрос");
        }
        if ((int)trim($request->captcha) !== (int)($_SESSION['captcha'] ?? 0)) {
            return self::_error_arr("Неверный ответ, обновите капчу");
        }

        // Check if company with this INN already exists for this main_company
        $existing = $db->getOne("SELECT c.id FROM company c 
            LEFT JOIN user_companys uc ON uc.company_id=c.id 
            WHERE c.inn=?s AND uc.main_company_id=?i", $request->inn, $main_company_id);
        if ($existing) {
            return self::_error_arr("Компания с таким ИНН уже зарегистрирована");
        }

        // Create company
        $company = new Company();
        $company->name = trim($request->contact_name);
        $company->mphone = preg_replace("/\D+/", "", $request->phone);
        $company->email = $request->email;
        $company->inn = $request->inn;
        $company->type = 1; // legal entity
        $company->btype = 1; // client
        $company->save();

        if (!$company->id) {
            return self::_error_arr("Не удалось создать компанию");
        }

        // Save company card file if provided
        if (!empty($request->company_card_file)) {
            self::_saveCompanyCardFile($request->company_card_file, $company->id, $main_company_id);
        }

        // Generate password
        $pass = self::_generatePassword();

        // Create user
        $user = new User();
        $user->username = $request->email;
        $user->email = $request->email;
        $user->password = $pass;
        $user->name = trim($request->contact_name);
        $user->mphone = preg_replace("/\D+/", "", $request->phone);
        $user->company_id = $company->id;
        $user->main_company_id = $main_company_id;
        $user->roles = 10;
        $user_err = $user->save();

        if ($user_err) {
            return self::_error_arr("Не удалось создать пользователя: " . $user_err);
        }

        // Link user to company
        $db->query("INSERT INTO user_companys (user_id, main_company_id, company_id, btype, create_date) 
            VALUES (?i, ?i, ?i, ?i, ?s)", $user->id, $main_company_id, $company->id, 1, date("Y-m-d H:i:s"));

        // Create balance record
        $cb = new CompanyBalance($company->id);
        $cb->Save();

        return [
            "status" => "ok",
            "msg" => "Регистрация прошла успешно. Данные для входа отправлены на указанный email.",
            "company_id" => (int)$company->id,
            "user_id" => (int)$user->id
        ];
    }

    /**
     * 2. Get finance info: balance, reserve, credit limit, funds in work
     */
    public static function get_finance_info($request) {
        $db = DB::getInstance();
        $company_id = (int)($request->company_id ?? $_SESSION['company_id'] ?? 0);
        if ($company_id <= 0) {
            return self::_error_arr("Не указана компания");
        }

        $main_company_id = (int)($_SESSION['main_company'] ?? 0);
        if ($main_company_id <= 0) {
            return self::_error_arr("Не определена основная компания");
        }

        // Ensure balance record exists
        $balance = $db->getRow("SELECT * FROM company_balance WHERE company_id=?i AND main_company_id=?i", $company_id, $main_company_id);
        if (!$balance) {
            $cb = new CompanyBalance($company_id);
            $cb->Save();
            $balance = $db->getRow("SELECT * FROM company_balance WHERE company_id=?i AND main_company_id=?i", $company_id, $main_company_id);
        }

        // Credit limit from active dogovor
        $credit_limit = $db->getOne("SELECT COALESCE(MAX(credit_limit),0) FROM dogovor 
            WHERE company_id=?i AND main_company=?i AND deleted=0", $company_id, $main_company_id);

        return [
            "status" => "ok",
            "balance" => (float)($balance['balance'] ?? 0),
            "rezerv" => (float)($balance['rezerv'] ?? 0),
            "credit_limit" => (float)$credit_limit,
            "sum_trade" => (float)($balance['sum_trade'] ?? 0),
            "company_id" => $company_id
        ];
    }

    /**
     * 3. Get my payments (simple list)
     */
    public static function get_my_payments($request) {
        $db = DB::getInstance();
        $company_id = (int)($request->company_id ?? $_SESSION['company_id'] ?? 0);
        if ($company_id <= 0) {
            return self::_error_arr("Не указана компания");
        }
        $main_company_id = (int)($_SESSION['main_company'] ?? 0);

        $date_from = $request->date_from ?? date("Y-m-d", strtotime("-30 days"));
        $date_to = $request->date_to ?? date("Y-m-d");

        $payments = $db->getAll("SELECT p.id, p.summ, p.create_date, p.payment_type, p.payment_direction, p.payment_num, p.payment_target, p.is_advance 
            FROM payment p 
            WHERE p.company_id=?i AND p.main_company_id=?i AND p.deleted=0 
            AND p.create_date BETWEEN ?s AND ?s 
            ORDER BY p.create_date DESC", 
            $company_id, $main_company_id, $date_from . " 00:00:00", $date_to . " 23:59:59");

        return [
            "status" => "ok",
            "payments" => $payments ?? []
        ];
    }

    /**
     * 4. Get shipments (documents type_id=2)
     */
    public static function get_shipments($request) {
        $db = DB::getInstance();
        $company_id = (int)($request->company_id ?? $_SESSION['company_id'] ?? 0);
        if ($company_id <= 0) {
            return self::_error_arr("Не указана компания");
        }
        $main_company_id = (int)($_SESSION['main_company'] ?? 0);

        $date_from = $request->date_from ?? date("Y-m-d", strtotime("-90 days"));
        $date_to = $request->date_to ?? date("Y-m-d");

        $shipments = $db->getAll("SELECT d.id, d.number, d.document_date, d.zakaz_id, 
            (SELECT COUNT(*) FROM document_details WHERE document_id=d.id AND deleted=0) as positions_count,
            (SELECT COALESCE(SUM(price*count),0) FROM document_details WHERE document_id=d.id AND deleted=0 AND detail_id<>0) as summa,
            d.chf_number, d.chf_date
            FROM document d 
            WHERE d.company_id=?i AND d.main_company=?i AND d.type_id=2 AND d.deleted=0
            AND d.document_date BETWEEN ?s AND ?s
            ORDER BY d.document_date DESC",
            $company_id, $main_company_id, $date_from, $date_to . " 23:59:59");

        return [
            "status" => "ok",
            "shipments" => $shipments ?? []
        ];
    }

    /**
     * 5. Get returns (documents type_id=6 — возврат от покупателя), date filter only
     */
    public static function get_returns($request) {
        $db = DB::getInstance();
        $company_id = (int)($request->company_id ?? $_SESSION['company_id'] ?? 0);
        if ($company_id <= 0) {
            return self::_error_arr("Не указана компания");
        }
        $main_company_id = (int)($_SESSION['main_company'] ?? 0);
        if ($main_company_id <= 0) {
            return self::_error_arr("Не определена основная компания");
        }

        $date_from = $request->date_from ?? "";
        $date_to = $request->date_to ?? "";

        $base_sql = "SELECT d.id, d.number, d.document_date, d.zakaz_id, d.comment,
            (SELECT COALESCE(SUM(price*count),0) FROM document_details WHERE document_id=d.id AND deleted=0 AND detail_id<>0) as summa,
            (SELECT COUNT(*) FROM document_details WHERE document_id=d.id AND deleted=0) as positions_count
            FROM document d 
            WHERE d.company_id=?i AND d.main_company=?i AND d.type_id=6 AND d.deleted=0";

        if (!empty($date_from) && !empty($date_to)) {
            $returns = $db->getAll($base_sql . " AND d.document_date BETWEEN ?s AND ?s ORDER BY d.document_date DESC",
                $company_id, $main_company_id, $date_from, $date_to . " 23:59:59");
        } else {
            $returns = $db->getAll($base_sql . " ORDER BY d.document_date DESC",
                $company_id, $main_company_id);
        }

        return [
            "status" => "ok",
            "returns" => $returns ?? []
        ];
    }

    /**
     * Generate akt sverki link
     */
    public static function generate_akt_sverki($request) {
        $company_id = (int)($request->company_id ?? $_SESSION['company_id'] ?? 0);
        if ($company_id <= 0) {
            return self::_error_arr("Не указана компания");
        }
        $date_from = $request->date_from ?? date("Y-m-01");
        $date_to = $request->date_to ?? date("Y-m-d");

        return [
            "status" => "ok",
            "url" => "/akt_sverki.php?company_id=" . $company_id . "&date_from=" . urlencode($date_from) . "&date_to=" . urlencode($date_to)
        ];
    }

    /**
     * Print invoice HTML from schet.php
     */
    public static function print_invoice($request) {
        $document_id = (int)($request->document_id ?? 0);
        if ($document_id <= 0) {
            return self::_error_arr("Не указан документ");
        }
        $db = DB::getInstance();
        $doc = $db->getRow("SELECT d.* FROM document d WHERE d.id=?i AND d.deleted=0", $document_id);
        if (!$doc) {
            return self::_error_arr("Документ не найден");
        }

        $zakaz_id = (int)($doc['zakaz_id'] ?? 0);
        if ($zakaz_id <= 0) {
            return self::_error_arr("У документа отсутствует zakaz_id");
        }

        $oldGet = $_GET;
        $oldRequest = $_REQUEST;
        $oldServer = $_SERVER;

        $_GET['zakaz_id'] = $zakaz_id;
        $_REQUEST['zakaz_id'] = $zakaz_id;
        $_SERVER['REQUEST_URI'] = '/schet.php?zakaz_id=' . $zakaz_id;
        $_SERVER['SCRIPT_NAME'] = '/schet.php';

        ob_start();
        $schetFile = dirname(__DIR__, 4) . '/schet.php';
        if (!file_exists($schetFile)) {
            ob_end_clean();
            $_GET = $oldGet;
            $_REQUEST = $oldRequest;
            $_SERVER = $oldServer;
            return self::_error_arr("Не найден файл schet.php");
        }

        include $schetFile;
        $html = ob_get_clean();

        $_GET = $oldGet;
        $_REQUEST = $oldRequest;
        $_SERVER = $oldServer;

        if ($html === false || $html === '') {
            return self::_error_arr("Не удалось получить счет с /schet.php?zakaz_id=" . $zakaz_id);
        }

        return [
            "status" => "ok",
            "html" => base64_encode($html),
            "filename" => "schet_" . ($doc['number'] ?: $doc['id']) . ".html"
        ];
    }

    /**
     * Print UPD (reuses existing get_upd_xls)
     */
    public static function print_upd($request) {
        $document_id = (int)($request->document_id ?? 0);
        if ($document_id <= 0) {
            return self::_error_arr("Не указан документ");
        }
        $req = new \stdClass();
        $req->document_id = $document_id;
        return Documents::get_upd_xls($req);
    }

    private static function _generatePassword($length = 8) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }

    private static function _saveCompanyCardFile($fileData, $companyId, $mainCompanyId) {
        // Simple base64 file save
        if (empty($fileData)) return "";
        $uploadDir = "/var/www/shop_relize/api/files/company_cards/";
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        if (preg_match('/^data:([a-zA-Z0-9\/\+]+);base64,/', $fileData, $matches)) {
            $extension = "pdf";
            if (isset($matches[1])) {
                $mime = $matches[1];
                $extMap = [
                    "application/pdf" => "pdf",
                    "image/jpeg" => "jpg",
                    "image/png" => "png"
                ];
                $extension = $extMap[$mime] ?? "bin";
            }
            $fileData = substr($fileData, strpos($fileData, ',') + 1);
            $fileData = base64_decode($fileData);
            if ($fileData === false) return "";
            $fileName = "company_card_" . $mainCompanyId . "_" . $companyId . "_" . time() . "." . $extension;
            $filePath = $uploadDir . $fileName;
            if (file_put_contents($filePath, $fileData)) {
                return "/api/files/company_cards/" . $fileName;
            }
        }
        return "";
    }
}
