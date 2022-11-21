import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'

export class PaymentService {
    constructor () {
        this.repository = Repository.get('credential')
        this.paymentRequest = {
            meterSerial: null,
            amount: null,
        }
    }

    async startTransaction (companyId) {
        try {
            let paymentRequest  = {
                meterSerial: this.paymentRequest.meterSerial,
                amount: this.paymentRequest.amount,
            }
            let response = await this.repository.post(paymentRequest,companyId)
            if (response.status === 200 || response.status === 201) {
                return  response.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

}