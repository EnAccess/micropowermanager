import RepositoryFactory from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class CurrencyListService {
    constructor() {
        this.repository = RepositoryFactory.get('currencyList')
        this.currencyList = []
    }

    updateList(currencyData) {
        this.currencyList = []

        for (let [k, v] of Object.entries(currencyData)) {
            let currency = {
                name: k,
                symbol: v.symbol,
            }
            this.currencyList.push(currency)
        }
    }
    async list() {
        try {
            let response = await this.repository.list()
            if (response.status === 200) {
                this.updateList(response.data.data)
                return this.currencyList
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let erorMessage = e.response.data.message
            return new ErrorHandler(erorMessage, 'http')
        }
    }
}
