import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'

export class AssetRateService {
    constructor () {
        this.repository = Repository.get('assetRate')

    }

    async  editAssetRate (rate, adminId, personId) {
        try {
            let terms = {
                newCost: rate.tempCost,
                cost: rate.rate_cost,
                admin_id: adminId,
                person_id: personId
            }

            let response = await this.repository.update(rate.id, terms)

            if (response.status === 200 || response.status === 201) {
                return response
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
