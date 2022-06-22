import Repository from '../repositories/RepositoryFactory'

export class MinutelyConsumptionService {

    constructor (meterAddress) {
        this.repository = Repository.get('minutely')
        this.list = []
        this.pagingUrl = `/api/kelin-meters/kelin-meter/minutely-consumptions/${meterAddress}`
        this.routeName = `/kelin-meters/kelin-meter/minutely-consumptions/${meterAddress}`

    }
    updateList (responseData) {
        this.list = []
        for (let data of responseData) {
            this.list.push(data.data.attributes)
        }
    }
}