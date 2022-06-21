import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'
import { EventBus } from '../eventbus'

export class CustomerService {

    constructor () {
        this.repository = Repository.get('customer')
        this.list = []
        this.isSync = false
        this.pagingUrl = '/api/kelin-meters/kelin-customer'
        this.routeName = '/kelin-meters/kelin-customer'
        this.customer = {
            id: null,
            customerNo: null,
            mpmPerson: null,
            phone: null,
            address: null,
        }
    }


    updateList (data) {
        this.list = []
        for (let c in data) {
            this.list.push(data[c].data.attributes)
        }
    }

    async syncCustomers () {
        try {
            const response = await this.repository.sync()
            if (response.status === 200) {
                return this.updateList(response.data.data)
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async checkCustomers () {
        try {
            const response = await this.repository.syncCheck()
            if (response.status === 200) {
                return response.data.data.result

            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }



    async getCustomerName (customerId) {
        try {
            let response = await this.repository.get(customerId)
            if (response.status === 200) {
                return response.data.data.name
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    search (term) {
        this.pagingUrl = '/api/kelin-meters/kelin-customer/advanced/search'
        EventBus.$emit('loadPage', this.pagingUrl, { 'term': term })
    }

    showAll () {
        this.pagingUrl = '/api/kelin-meters/kelin-customer'
        EventBus.$emit('loadPage', this.pagingUrl, {})
    }
}