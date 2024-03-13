import { resources } from '@/resources'

export class BatchRevenue {
    constructor() {
        this.revenueList = null
    }

    revenueForPeriod(targetId, targetType, startDate, endDate) {
        return axios
            .post(resources.revenues.batch, {
                target_type_id: targetId,
                target_type: targetType,
                start_date: startDate,
                end_date: endDate,
            })
            .then((response) => {
                return response.data.data
            })
    }

    periodData(period) {
        return this.revenueList[period]
    }
}
