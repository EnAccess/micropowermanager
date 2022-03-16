const resource = '/api/reports'
import Client from './Client/AxiosClient'
export default {

    list(type) {
        return Client.get(`${resource}?type=`+type)
    },
    download(id,reference) {
        return `${resource}/`+`${id}`+`${reference}`
    }
}
