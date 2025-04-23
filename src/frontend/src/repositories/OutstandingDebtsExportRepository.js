import Client, { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/export/debts`
export default {
  download() {
    return Client.get(resource, {
      responseType: "blob",
    })
  },
}
