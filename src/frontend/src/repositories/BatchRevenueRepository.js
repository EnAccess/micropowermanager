import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/revenue`

export default {
  getRevenueForPeriod(targetPeriod) {
    return Client.post(`${resource}`, targetPeriod)
  },
}
