using System;
using System.Globalization;
using System.Windows.Data;
using System.Windows.Media;

namespace ParfumAdmin_WPF.Helpers
{
    public class StockToColorConverter : IValueConverter
    {
        public const int LowStockThreshold = 10;

        private static readonly SolidColorBrush OutBrush  = Freeze(Color.FromRgb(220, 38, 38));
        private static readonly SolidColorBrush LowBrush  = Freeze(Color.FromRgb(234, 88, 12));
        private static readonly SolidColorBrush OkBrush   = Freeze(Color.FromRgb(22, 163, 74));
        private static readonly SolidColorBrush NoneBrush = Freeze(Color.FromRgb(42, 42, 62));

        private static SolidColorBrush Freeze(Color c)
        {
            var b = new SolidColorBrush(c);
            b.Freeze();
            return b;
        }

        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is int stock)
            {
                if (stock <= 0) return OutBrush;
                if (stock <= LowStockThreshold) return LowBrush;
                return OkBrush;
            }
            return NoneBrush;
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
            => throw new NotImplementedException();
    }
}
