import Client from "@/repositories/Client/AxiosClient.js"

export default {
  listForMeter(meterId) {
    return Client.get(`/api/meters/${meterId}/vending-providers`)
  },
}
