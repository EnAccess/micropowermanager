import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/steama-meters/steama-setting/sms-setting`

export default {
  update(smsListPM) {
    return Client.put(`${resource}`, smsListPM)
  },
}
