import Client from "@/repositories/Client/AxiosClient.js"

const resource = "/api/sms-transaction-parser/parsing-rules"

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(data) {
    return Client.post(`${resource}`, data)
  },
  update(id, data) {
    return Client.put(`${resource}/${id}`, data)
  },
  delete(id) {
    return Client.delete(`${resource}/${id}`)
  },
}
