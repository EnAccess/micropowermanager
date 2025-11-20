import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/meters`

export default {
  detail(meterSerial) {
    return Client.get(`${resource}/${meterSerial}`)
  },
  revenue(meterSerial) {
    return Client.get(`${resource}/${meterSerial}/revenue`)
  },
  update(meterId, data) {
    return Client.put(`${resource}/${meterId}`, data)
  },
}
