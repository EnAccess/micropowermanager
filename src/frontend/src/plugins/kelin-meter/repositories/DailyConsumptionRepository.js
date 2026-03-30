import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/kelin-meters/kelin-meter/daily-consumptions`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
