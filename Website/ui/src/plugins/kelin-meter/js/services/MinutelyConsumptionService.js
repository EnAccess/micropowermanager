import MinutelyConsumptionRepository from "../repositories/MinutelyConsumptionRepository"

export class MinutelyConsumptionService {
  constructor(meterAddress) {
    this.repository = MinutelyConsumptionRepository
    this.list = []
    this.pagingUrl = `/api/kelin-meters/kelin-meter/minutely-consumptions/${meterAddress}`
    this.routeName = `/kelin-meters/kelin-meter/minutely-consumptions/${meterAddress}`
  }
  updateList(responseData) {
    this.list = []
    for (let data of responseData) {
      this.list.push(data.data.attributes)
    }
  }
}
