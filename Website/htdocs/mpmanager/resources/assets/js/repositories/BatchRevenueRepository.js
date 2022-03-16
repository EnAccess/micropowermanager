const resource = '/api/revenue'
import Client from './Client/AxiosClient'
export default {
    getRevenueForPeriod(targetPeriod){
        return Client.post(`${resource}` , targetPeriod)
    }
}
