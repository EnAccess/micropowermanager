import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'

export class PaymentService {
    constructor() {
        this.repository = Repository.get('payment')
        this.paymentRequest = {
            meterSerial: null,
            amount: null,
        }
    }

    async startTransaction(companyId) {
        try {
            let paymentRequest = {
                meterSerial: this.paymentRequest.meterSerial,
                amount: this.paymentRequest.amount,
            }
            const { data } = await this.repository.post(
                paymentRequest,
                companyId,
            )

            console.log(data)
            if (data.data.redirectionUrl) {
                return data.data
            } else {
                return new ErrorHandler(data.data.error, 'http', 400)
            }
        } catch (error) {
            let errorMessage = ''
            console.log(error)
            if (error.response) {
                errorMessage = error.response.data.message
            } else {
                errorMessage = error.message
            }
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
