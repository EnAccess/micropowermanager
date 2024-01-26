import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/export/transactions`
export default {

    download(email,slug) {
        return `${resource}/${email}?${slug}`
    }
}