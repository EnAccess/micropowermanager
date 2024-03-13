import { baseUrl } from '@/repositories/Client/AxiosClient'
import { resources } from '@/resources'

export class Meters {
    constructor() {
        this.meters = []
    }

    addMeter(meter) {
        this.meters.push(meter)
    }

    intialize(meters) {
        this.meters = meters
    }

    setDetail(meterData) {
        this.meters = meterData
    }

    getMeterDetails(meterId) {
        return axios
            .get(`${baseUrl}${resources.meters.getMeters}${meterId}/all`)
            .then((response) => {
                let data = response.data.data
                let meter = {
                    id: meterId,
                    meter_parameter: data.meter_parameter,
                    serial_number: data.serial_number,
                    max_current: data.meter_type.max_current,
                    phase: data.meter_type.phase,
                    tariff: {
                        id: data.meter_parameter.tariff.id,
                        name: data.meter_parameter.tariff.name,
                        price: data.meter_parameter.tariff.price,
                    },
                    geo: [
                        data.meter_parameter.geo != null
                            ? data.meter_parameter.geo.points.split(',')[0]
                            : -1.876232,
                        data.meter_parameter.geo != null
                            ? data.meter_parameter.geo.points.split(',')[1]
                            : 33.02232,
                    ],
                }
                return meter
            })
    }

    getAllData() {
        this.meters.forEach((v, k) => {
            axios
                .get(resources.meters.getMeters + v + '/all')
                .then((response) => {
                    let data = response.data.data
                    this.meters[k] = {
                        id: v,
                        serial_number: data.serial_number,
                        max_current: data.meter_type.max_current,
                        phase: data.meter_type.phase,
                        tariff: {
                            id: data.meter_parameter.tariff.id,
                            name: data.meter_parameter.tariff.name,
                            price: data.meter_parameter.tariff.price,
                        },
                        geo: [
                            data.geo != null
                                ? data.geo.split(',')[0]
                                : -1.876232,
                            data.geo != null
                                ? data.geo.split(',')[1]
                                : 33.02232,
                        ],
                    }
                })
        }) //end foreach
        return this
    }

    getMeters() {
        return this.meters
    }
}
