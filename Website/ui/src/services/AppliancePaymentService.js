import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { convertObjectKeysToSnakeCase } from '@/Helpers/Utils'
export class AppliancePaymentService {
    constructor() {
        this.repository = Repository.get('appliancePayment')
    }

    async getPaymentForAppliance(applianceId, payment) {
        const paymentParams = convertObjectKeysToSnakeCase(payment)
        try {
            const { data, status, error } = await this.repository.update(
                applianceId,
                paymentParams,
            )
            if (status !== 200 && status !== 201)
                return new ErrorHandler(error, 'http', status)

            return data.data
        } catch (e) {
            const errorMessage = e.response.data.data.message[0]
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
