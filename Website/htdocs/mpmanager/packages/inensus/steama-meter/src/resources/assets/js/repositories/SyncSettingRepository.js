const resource = '/api/steama-meters/steama-setting/sync-setting'
import Client from './Client/AxiosClient'

export default {

    update(syncListPM) {
        return Client.put(`${resource}`, syncListPM)
    },

}