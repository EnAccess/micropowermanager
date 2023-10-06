import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/reports`

export default {

    list(type) {
        return Client.get(`${resource}?type=`+type)
    },
    download(id,reference,companyId) {
        return `${baseUrl}/api/report-downloading/${id}/${reference}/${companyId}`
    }
}
