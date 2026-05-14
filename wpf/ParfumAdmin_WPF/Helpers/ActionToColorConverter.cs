using System;
using System.Globalization;
using System.Windows.Data;
using System.Windows.Media;

namespace ParfumAdmin_WPF.Helpers
{
    public class ActionToColorConverter : IValueConverter
    {
        private static readonly SolidColorBrush Green  = Freeze(Color.FromRgb(0x16, 0xA3, 0x4A));
        private static readonly SolidColorBrush Blue   = Freeze(Color.FromRgb(0x25, 0x63, 0xEB));
        private static readonly SolidColorBrush Red    = Freeze(Color.FromRgb(0xDC, 0x26, 0x26));
        private static readonly SolidColorBrush Orange = Freeze(Color.FromRgb(0xEA, 0x58, 0x0C));
        private static readonly SolidColorBrush Purple = Freeze(Color.FromRgb(0x7C, 0x3A, 0xED));
        private static readonly SolidColorBrush Grey   = Freeze(Color.FromRgb(0x55, 0x55, 0x70));

        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            return value as string switch
            {
                "created"         => Green,
                "updated"         => Blue,
                "deleted"         => Red,
                "bulk_deleted"    => Red,
                "bulk_updated"    => Orange,
                "status_changed"  => Purple,
                "payment_changed" => Purple,
                _                 => Grey,
            };
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
            => throw new NotImplementedException();

        private static SolidColorBrush Freeze(Color c)
        {
            var b = new SolidColorBrush(c);
            b.Freeze();
            return b;
        }
    }
}
