import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/revenue`

export default {
  getRevenueForPeriod(targetPeriod) {
    return Client.post(`${resource}`, targetPeriod)
  },
}
