export const currency = {
    methods: {
        readable (amount, seperator = ',') {

            if (typeof (amount) === 'undefined' || amount === null) return 0
            if (parseFloat(amount).toString() !== amount.toString()) {
                return amount
            }
            // string to array
            let amountArr = amount.toString().replace(' ', '').split('.')

            let commaNumber = ''
            if (amountArr.length > 1) {
                commaNumber = amountArr[1].slice(0,
                    amountArr[1].length >= 3 ? 2 : amountArr[1].length)

            }

            //let commaNum = Math.floor((amountArr.length - 1) / 3)
            let result = ''
            for (let i = amountArr[0].length - 1; i >= 0; i--) {
                if ((amountArr[0].length - 1 - i) % 3 === 0 && (amountArr[0].length - 1 - i) >= 3) {
                    result += seperator
                }
                result += amountArr[0][i]

            }
            if (commaNumber === '')
                return result.split('').reverse().join('')
            return result.split('').reverse().join('') + '.' + commaNumber
        },
        moneyFormat(amount){
            const formatter = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2
            })

            return formatter.format(amount)
        }
    },
}
