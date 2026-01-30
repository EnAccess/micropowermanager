import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/appliances/payment`

export default {
  update(id, data) {
    return Client.post(`${resource}/${id}`, data)
  },
  checkStatus(transactionId) {
    return Client.get(`${resource}/status/${transactionId}`)
  },
}
