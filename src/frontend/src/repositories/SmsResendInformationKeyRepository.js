import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

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
