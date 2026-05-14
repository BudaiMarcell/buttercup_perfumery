using System;
using System.IO;
using System.Security.Cryptography;
using System.Text;

namespace ParfumAdmin_WPF.Services
{
    public class TokenStore
    {
        private static readonly string AppFolder = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "ParfumAdmin");

        private static readonly string FilePath = Path.Combine(AppFolder, "token.bin");

        private static readonly byte[] Entropy = Encoding.UTF8.GetBytes("ParfumAdmin/token");

        private readonly object _gate = new();
        private string? _cachedToken;

        public string? Load()
        {
            lock (_gate)
            {
                if (_cachedToken != null) return _cachedToken;

                if (!File.Exists(FilePath)) return null;

                try
                {
                    var encrypted = File.ReadAllBytes(FilePath);
                    var plain = ProtectedData.Unprotect(encrypted, Entropy, DataProtectionScope.CurrentUser);
                    _cachedToken = Encoding.UTF8.GetString(plain);
                    return _cachedToken;
                }
                catch (Exception)
                {
                    return null;
                }
            }
        }

        public void Save(string token)
        {
            if (string.IsNullOrEmpty(token)) return;

            lock (_gate)
            {
                Directory.CreateDirectory(AppFolder);
                var plain = Encoding.UTF8.GetBytes(token);
                var encrypted = ProtectedData.Protect(plain, Entropy, DataProtectionScope.CurrentUser);
                File.WriteAllBytes(FilePath, encrypted);
                _cachedToken = token;
            }
        }

        public void Clear()
        {
            lock (_gate)
            {
                _cachedToken = null;
                try
                {
                    if (File.Exists(FilePath)) File.Delete(FilePath);
                }
                catch (Exception)
                {
                }
            }
        }
    }
}
