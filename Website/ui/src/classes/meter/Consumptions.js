import { resources } from '@/resources'

export class Consumptions {
    constructor(meterId) {
        this.data = []
        this.meterId = meterId
    }

    getData(start, end) {
        this.data = []
        return axios
            .get(
                resources.meters.consumptions +
                    this.meterId +
                    '/consumptions/' +
                    start +
                    '/' +
                    end,
            )
            .then((response) => {
                for (let c in response.data.data) {
                    let item = response.data.data[c]
                    this.data.push([
                        item.reading_date,
                        item.consumption,
                        item.credit_on_meter,
                    ])
                }
            })
    }
}
