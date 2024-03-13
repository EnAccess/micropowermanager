import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/sms-resend-information-key`

export default {
    list() {
        return Client.get(`${resource}`)
    },
    update(smsResendInformationKey) {
        return Client.put(
            `${resource}/${smsResendInformationKey.id}`,
            smsResendInformationKey,
        )
    },
}
