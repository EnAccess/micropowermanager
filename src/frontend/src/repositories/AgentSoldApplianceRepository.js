import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/agents/sold`

export default {
  create(soldAppliance) {
    return Client.post(`${resource}`, soldAppliance)
  },
}
