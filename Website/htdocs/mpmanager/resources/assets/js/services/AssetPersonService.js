import Repository from '../repositories/RepositoryFactory'
import {ErrorHandler} from '../Helpers/ErrorHander'

export class AssetPersonService {
    constructor() {
        this.repository = Repository.get('assetPerson')
        this.list = []


    }
    fromJson(data){
        return {
            applianceType: data.asset_type,
            applianceTypeId: data.asset_type_id,
            creatorId: data.creator_id,
            creatorType: data.creator_type,
            downPayment: data.down_payment,
            createdAt: data.created_at,
            firstPaymentDate: data.first_payment_date,
            personId: data.person_id,
            rateCount: data.rate_count,
            totalCost: data.total_cost,
            totalRemainingAmount: data.totalRemainingAmount,
            totalPayments: data.totalPayments,
            rates: data.rates,
            logs: data.logs,
        }
    }
    async getPersonAssets(id){
        try {
            let response = await this.repository.list(id)
            if (response.status === 200 || response.status === 201) {
                this.list = response.data.data
                return this.list
            } else {
                new ErrorHandler(response.error, 'http', response.status)
            }

        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
    async show(applianceId){
        try {
            let response = await this.repository.show(applianceId)
            if (response.status === 200) {
                return this.fromJson(response.data.data)
            }else{
                new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
    async saveAsset(id,personId,assetPM,creatorId){
        try {
            assetPM.creatorId = creatorId
            let response = await this.repository.create(id,personId,assetPM)
            if (response.status === 200 || response.status === 201) {
                return response.data.data
            } else {
                new ErrorHandler(response.error, 'http', response.status)
            }

        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

}
