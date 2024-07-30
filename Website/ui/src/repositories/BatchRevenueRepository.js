import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/revenue`

export default {
  getRevenueForPeriod(targetPeriod) {
    return Client.post(`${resource}`, targetPeriod)
  },
}
