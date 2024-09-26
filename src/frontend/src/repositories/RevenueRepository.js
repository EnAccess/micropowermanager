import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/revenue`

export default {
  trends(miniGridId, period) {
    return Client.post(`${resource}/trends/${miniGridId}`, period)
  },
  tickets(miniGridId) {
    return Client.get(`${resource}/tickets/${miniGridId}`)
  },
}
