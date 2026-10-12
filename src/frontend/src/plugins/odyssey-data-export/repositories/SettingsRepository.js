import Client, { baseUrl } from "@/repositories/Client/AxiosClient.js"

const resource = `${baseUrl}/api/odyssey-data-export/settings`

export default {
  get() {
    return Client.get(resource)
  },
  update(settings) {
    return Client.put(resource, settings)
  },
}
