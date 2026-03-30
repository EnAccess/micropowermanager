import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/steama-meters/steama-setting/feedback-word`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  put(feedBackWords) {
    return Client.put(`${resource}/${feedBackWords.id}`, feedBackWords)
  },
}
