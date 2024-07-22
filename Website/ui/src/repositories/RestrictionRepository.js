import Client from '@/repositories/Client/AxiosClient'
const resourcePurchase =
    'https://stripe.micropowermanager.com/api/mpm/checkPurchaseCode'
import { baseUrl } from '@/repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/restrictions`

export default {
    sendCode(purchase_PM) {
        return Client.post(`${resourcePurchase}`, purchase_PM)
    },
    check(restriction_PM) {
        return Client.post(`${resource}`, restriction_PM)
    },
}
