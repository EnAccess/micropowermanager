const resource = '/api/sms-resend-information-key'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
    update (smsResendInformationKey) {
        return Client.put(`${resource}/${smsResendInformationKey.id}`, smsResendInformationKey)
    }

}