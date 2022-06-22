const resource = '/api/spark-meters/sm-setting/sync-setting'
import Client from './Client/AxiosClient'

export default {

    update (syncListPM) {
        return Client.put(`${resource}`, syncListPM)
    },

}