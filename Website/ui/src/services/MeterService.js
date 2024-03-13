import RepositoryFactory from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class MeterService {
    constructor() {
        this.repository = RepositoryFactory.get('meter')
        this.meters = []
        this.selectedMeter = null
        this.meter = {
            id: null,
            meter_parameter: null,
            serial_number: null,
            max_current: null,
            phase: null,
            tariff: {
                id: null,
                name: null,
                price: null,
            },
            geo: [],
        }
    }

    async getMeterGeos(miniGridId) {
        try {
            let response = await this.repository.geoList(miniGridId)

            if (response.status === 200) {
                this.meters = response.data.data
                return this.meters
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            return new ErrorHandler(e, 'http')
        }
    }

    async getMeterDetails(meterId) {
        try {
            let response = await this.repository.get(meterId)

            if (response.status === 200) {
                let data = response.data.data
                let points = [0, 0]
                if (data.meter_parameter.geo != null) {
                    points = data.meter_parameter.geo.points.split(',')
                }
                this.meter = {
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
                    geo: [points[0], points[1]],
                }
                return this.meter
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            return new ErrorHandler(e, 'http')
        }
    }

    async updateMeter(meters) {
        try {
            let response = await this.repository.update(meters)
            if (response.status === 200) {
                return response
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            return new ErrorHandler(e, 'http')
        }
    }

    getMeters() {
        return this.meters
    }

    addMeter(meter) {
        this.meters.push(meter)
    }
}
