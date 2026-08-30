using System;
using System.IO;
using System.Runtime.InteropServices;

namespace Sort1.Common
{
    /// <summary>
    /// Получение UUID машины для формирования hardwareid лицензии.
    ///
    /// Зачем нужен: PHP-код получал UUID через exec("sudo dmidecode") /
    /// exec("powershell ..."), но PeachPie выполняет exec() через
    /// cmd.exe /c "команда", ломая вложенные кавычки, поэтому UUID
    /// всегда был пустым и сервер лицензирования отвечал
    /// "Неправильный hardwareid". Здесь UUID читается напрямую из ОС.
    /// Вызывается из PHP через CLR-interop: \Sort1\Common\MachineUuid::Get().
    /// </summary>
    public static class MachineUuid
    {
        /// <summary>Возвращает UUID машины (или пустую строку, если не удалось).</summary>
        public static string Get()
        {
            if (RuntimeInformation.IsOSPlatform(OSPlatform.Windows))
                return GetWindowsUuid();

            return GetLinuxUuid();
        }

        static string GetWindowsUuid()
        {
            // 1. MachineGuid из реестра — уникальный ID установки Windows,
            //    читается без прав администратора.
            try
            {
                using (var key = Microsoft.Win32.Registry.LocalMachine
                    .OpenSubKey(@"SOFTWARE\Microsoft\Cryptography"))
                {
                    var value = key?.GetValue("MachineGuid")?.ToString();
                    if (IsValidUuid(value)) return value;
                }
            }
            catch { }

            // 2. SMBIOS UUID через WMI (требует пакет System.Management;
            //    если недоступен — просто пропускаем).
            try
            {
                var type = Type.GetType(
                    "System.Management.ManagementObjectSearcher, System.Management");
                if (type != null)
                {
                    dynamic searcher = Activator.CreateInstance(type,
                        new object[] { "SELECT UUID FROM Win32_ComputerSystemProduct" });
                    foreach (var item in searcher.Get())
                    {
                        var value = item["UUID"]?.ToString();
                        if (IsValidUuid(value)) return value;
                    }
                }
            }
            catch { }

            return "";
        }

        static string GetLinuxUuid()
        {
            // 1. sysfs — не требует sudo, если файл доступен для чтения.
            var uuid = TryRead("/sys/class/dmi/id/product_uuid");
            if (IsValidUuid(uuid)) return uuid;

            // 2. /etc/machine-id — читается всегда; форматируем как UUID.
            var mid = TryRead("/etc/machine-id");
            if (!string.IsNullOrEmpty(mid) && mid.Length == 32)
            {
                return string.Join("-",
                    mid.Substring(0, 8), mid.Substring(8, 4),
                    mid.Substring(12, 4), mid.Substring(16, 4),
                    mid.Substring(20, 12));
            }

            return "";
        }

        static string TryRead(string path)
        {
            try { return File.ReadAllText(path).Trim(); }
            catch { return ""; }
        }

        static bool IsValidUuid(string value)
        {
            if (string.IsNullOrEmpty(value)) return false;
            Guid guid;
            return Guid.TryParse(value, out guid);
        }
    }
}
