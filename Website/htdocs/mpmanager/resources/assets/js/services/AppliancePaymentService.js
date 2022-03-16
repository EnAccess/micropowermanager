import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'

export  class AppliancePaymentService{

    constructor () {
        this.repository = Repository.get('appliancePayment')
    }

    async getPaymentForAppliance(selectedApplianceId, personId, adminId, rates, amount){
        let paymentPm = {
            'personId': personId,
            'adminId': adminId,
            'rates': rates,
            'amount': amount
        }
        try {
            let response = await this.repository.update(selectedApplianceId, paymentPm)
            if (response.status === 200 || response.status === 201) {
                return response
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        }catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
