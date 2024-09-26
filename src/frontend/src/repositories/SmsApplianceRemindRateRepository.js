import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/sms-appliance-remind-rate`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(smsApplianceRemindRate) {
    return Client.put(
      `${resource}/${smsApplianceRemindRate.id}`,
      smsApplianceRemindRate,
    )
  },
  create(smsApplianceRemindRate) {
    return Client.post(`${resource}`, smsApplianceRemindRate)
  },
}
