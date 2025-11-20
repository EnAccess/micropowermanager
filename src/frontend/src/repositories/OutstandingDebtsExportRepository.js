import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/export/debts`

export default {
  download() {
    return Client.get(resource, {
      responseType: "blob",
    })
  },
}
