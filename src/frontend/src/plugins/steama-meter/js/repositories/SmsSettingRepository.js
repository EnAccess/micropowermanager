import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/steama-meters/steama-setting/sms-setting`

export default {
  update(smsListPM) {
    return Client.put(`${resource}`, smsListPM)
  },
}
