import RepositoryFactory from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'
import { ConnectionsType } from '../classes/connection/ConnectionsType'

export class MeterDetailService {

    constructor (serialNumber) {
        this.repository = new RepositoryFactory.get('meterDetail')
        this.meter = {
            'id' : null,
            'loaded' : false,
            'registered' : null,
            'owner' : null,
            'total_revenue' : null,
            'last_payment' : null,
            'manufacturer' : null,
            'serialNumber' : serialNumber,
            'tariff' : null,
            'totalRevenue' : null,
            'meterType' : null,
        }

    }
    fromJson(data){
        const connectionType = new ConnectionsType()
        this.meter.registered = data.created_at
        this.meter.manufacturer = data.manufacturer
        this.meter.tariff = data.meter_parameter.tariff
        this.meter.owner = data.meter_parameter.owner
        this.meter.connection = connectionType.fromJson(data.meter_parameter.connection_type)  // TODO: get connection information
        this.meter.id = data.id
        this.meter.loaded = true
        this.meter.meterType = data.meter_type

        return this.meter
    }

    async detail () {
        try {
            let response = await this.repository.detail(this.meter.serialNumber)
            if(response.status === 200){
                return this.fromJson(response.data.data)
            }else{
                return new ErrorHandler(response.error, 'http', response.status)
            }
        }catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }

    async revenue(){
        try {
            let response = await this.repository.revenue(this.meter.serialNumber)
            if(response.status === 200){
                return response.data.data.revenue
            }else{
                return new ErrorHandler(response.error, 'http', response.status)
            }
        }catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }


}
