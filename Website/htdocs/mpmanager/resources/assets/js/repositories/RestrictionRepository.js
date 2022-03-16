import Client from './Client/AxiosClient'
const resource = 'api/restrictions'
const resourcePurchase = 'https://stripe.micropowermanager.com/api/mpm/checkPurchaseCode'
export default {
    sendCode (purchase_PM) {
        return Client.post(`${resourcePurchase}`, purchase_PM)
    },
    check (restriction_PM) {
        return Client.post(`${resource}`, restriction_PM)
    }
}
