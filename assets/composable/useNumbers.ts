export const useNumbers = () => {
  function formatPrice(price: number, currency: string = '₽') {
    return new Intl.NumberFormat('ru-RU').format(price) + ' ' + currency
  }

  function formatPriceWithSign(price: number, currency: string = '₽') {
    let sign = ''
    if (price > 0) {
      sign = '+ '
    } else if (price < 0) {
      sign = '- '
    }
    return sign + new Intl.NumberFormat('ru-RU').format(Math.abs(price)) + ' ' + currency
  }

  function formatPercent(percent: number, abs = false) {
    if (abs) {
      return Math.abs(percent) + '%'
    }
    return percent + '%'
  }

  function getPercent(resultPrice: number, currentPrice: number) {
    if (currentPrice > 0) {
      return parseFloat(String(Math.abs(resultPrice) / currentPrice * 100)).toFixed(2) + '%';
    }
    return '';
  }

  return {
    formatPrice,
    formatPriceWithSign,
    formatPercent,
    getPercent
  }
}
