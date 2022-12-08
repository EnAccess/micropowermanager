import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'

export class PaymentService {
    constructor () {
        this.repository = Repository.get('payment')
        this.paymentRequest = {
            meterSerial: null,
            amount: null,
        }
    }

    async startTransaction (companyId) {
        try {
            let paymentRequest = {
                meterSerial: this.paymentRequest.meterSerial,
                amount: this.paymentRequest.amount,
            }
            const response = await this.repository.post(paymentRequest, companyId)

            if (response.redirectionUrl) {
                return response
            } else {
                return new ErrorHandler(response.error, 'http', 400)
            }

        } catch (error) {
            const errorMessage = error.response.data.message

            return new ErrorHandler(errorMessage, 'http')
        }
    }

}