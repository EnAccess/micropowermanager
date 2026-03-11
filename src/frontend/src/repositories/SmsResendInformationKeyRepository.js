import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/sms-resend-information-key`

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
