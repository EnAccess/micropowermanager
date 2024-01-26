import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/export/debts`
export default {

    download(email,slug) {
        return `${resource}/${email}`
    }
}