<?php
/**
 * Глобальная эмуляция MySQLi функций для PeachPie
 */

// Проверяем, доступны ли функции MySQLi
if (!function_exists('mysqli_commit')) {
    function mysqli_commit($conn) {
        if (!$conn) return false;
        
        // Пробуем использовать PDO если доступно
        if (class_exists('PDO') && $conn instanceof PDO) {
            try {
                return $conn->commit();
            } catch (Exception $e) {
                error_log("PDO commit failed: " . $e->getMessage());
                return false;
            }
        }
        
        // Иначе пробуем выполнить COMMIT через запрос
        if (function_exists('mysqli_query')) {
            return mysqli_query($conn, "COMMIT");
        }
        
        error_log("mysqli_commit: no method available");
        return false;
    }
}

if (!function_exists('mysqli_autocommit')) {
    function mysqli_autocommit($conn, $mode) {
        if (!$conn) return false;
        
        $mode = $mode ? '1' : '0';
        
        if (function_exists('mysqli_query')) {
            return mysqli_query($conn, "SET autocommit = {$mode}");
        }
        
        error_log("mysqli_autocommit: no method available");
        return false;
    }
}

if (!function_exists('mysqli_rollback')) {
    function mysqli_rollback($conn) {
        if (!$conn) return false;
        
        if (function_exists('mysqli_query')) {
            return mysqli_query($conn, "ROLLBACK");
        }
        
        error_log("mysqli_rollback: no method available");
        return false;
    }
}

if (!function_exists('mysqli_begin_transaction')) {
    function mysqli_begin_transaction($conn) {
        if (!$conn) return false;
        
        if (function_exists('mysqli_query')) {
            return mysqli_query($conn, "START TRANSACTION");
        }
        
        error_log("mysqli_begin_transaction: no method available");
        return false;
    }
}

if (!function_exists('mysqli_real_escape_string')) {
    function mysqli_real_escape_string($conn, $string) {
        if (!$conn) return addslashes($string);
        
        if (function_exists('mysqli_real_escape_string')) {
            return mysqli_real_escape_string($conn, $string);
        }
        
        return addslashes($string);
    }
}
