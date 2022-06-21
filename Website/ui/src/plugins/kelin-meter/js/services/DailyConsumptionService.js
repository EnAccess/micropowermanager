import Repository from '../repositories/RepositoryFactory'

export class DailyConsumptionService {

    constructor (meterAddress) {
        this.repository = Repository.get('daily')
        this.list = []
        this.pagingUrl = `/api/kelin-meters/kelin-meter/daily-consumptions/${meterAddress}`
        this.routeName = `/kelin-meters/kelin-meter/daily-consumptions/${meterAddress}`

    }
    updateList (responseData) {
        this.list = []
        for (let data of responseData) {
            this.list.push(data.data.attributes)
        }
    }
}