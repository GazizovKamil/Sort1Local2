using System;
using System.IO;
using System.Net.Http;
using System.Text;

namespace Sort1.Common
{
    /// <summary>
    /// HTTP-клиент для обращений к серверу лицензирования Sort1.
    ///
    /// Зачем нужен: PeachPie (curl внутри скомпилированного PHP) игнорирует
    /// CURLOPT_SSL_VERIFYPEER / CURLOPT_SSL_VERIFYHOST, а сервер лицензирования
    /// использует самоподписанный сертификат (CN=as1.sort1.ru) без поддержки
    /// TLS 1.3. Поэтому curl_exec() из PHP всегда возвращает false
    /// ("The SSL connection could not be established").
    ///
    /// Этот клиент отключает валидацию сертификата и использует TLS 1.2,
    /// что позволяет успешно подключаться к серверу лицензирования.
    /// Вызывается из PHP через CLR-interop: \Sort1\Common\LicHttp::Get($url).
    /// </summary>
    public static class LicHttp
    {
        private static readonly HttpClient _client = CreateClient();

        /// <summary>Текст последней ошибки (пустая строка, если ошибки не было).</summary>
        public static string LastError { get; private set; } = "";

        /// <summary>Файл лога запросов/ответов (на Windows: C:\var\log\sort1\lichttp.log).</summary>
        public static string LogFile = "/var/log/sort1/lichttp.log";

        static void Log(string message)
        {
            try
            {
                var dir = Path.GetDirectoryName(LogFile);
                if (!string.IsNullOrEmpty(dir)) Directory.CreateDirectory(dir);
                File.AppendAllText(LogFile,
                    DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + " " + message + "\n");
            }
            catch { /* логирование не должно ломать запросы */ }
        }

        static HttpClient CreateClient()
        {
            var handler = new HttpClientHandler
            {
                // Сервер лицензирования использует самоподписанный сертификат.
                ServerCertificateCustomValidationCallback = (message, cert, chain, errors) => true,

                // Сервер не поддерживает TLS 1.3 (шлёт HandshakeFailure).
                SslProtocols = System.Security.Authentication.SslProtocols.Tls12
                             | System.Security.Authentication.SslProtocols.Tls11
                             | System.Security.Authentication.SslProtocols.Tls,
            };

            return new HttpClient(handler)
            {
                Timeout = TimeSpan.FromSeconds(30),
            };
        }

        /// <summary>Выполняет HTTP GET. При ошибке возвращает пустую строку (см. LastError).</summary>
        public static string Get(string url)
        {
            try
            {
                LastError = "";
                var result = _client.GetStringAsync(url).GetAwaiter().GetResult();
                Log("GET " + url + " => " + result);
                return result;
            }
            catch (Exception ex)
            {
                LastError = ex.Message;
                Log("GET " + url + " !! " + ex.Message);
                return "";
            }
        }

        /// <summary>Выполняет HTTP POST с телом JSON. При ошибке возвращает пустую строку (см. LastError).</summary>
        public static string PostJson(string url, string json)
        {
            try
            {
                LastError = "";
                using (var content = new StringContent(json ?? "", Encoding.UTF8, "application/json"))
                using (var response = _client.PostAsync(url, content).GetAwaiter().GetResult())
                {
                    var result = response.Content.ReadAsStringAsync().GetAwaiter().GetResult();
                    Log("POST " + url + " <= " + json + " => " + result);
                    return result;
                }
            }
            catch (Exception ex)
            {
                LastError = ex.Message;
                Log("POST " + url + " <= " + json + " !! " + ex.Message);
                return "";
            }
        }
    }
}
