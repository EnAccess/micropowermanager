import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/revenue`

export default {
  trends(miniGridId, period) {
    return Client.post(`${resource}/trends/${miniGridId}`, period)
  },
  tickets(miniGridId) {
    return Client.get(`${resource}/tickets/${miniGridId}`)
  },
}
