import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/people`
const documentResource = `/api/person-documents`

export default {
  get(page = 1) {
    return Client.get(`${resource}/${page}`)
  },
  update(person) {
    return Client.put(`${resource}/${person.id}`, person)
  },
  create(agentPm) {
    return Client.post(`${resource}`, agentPm)
  },
  delete(personId) {
    return Client.delete(`${resource}/${personId}`)
  },
  search(params) {
    return Client.get(`${resource}/search`, params)
  },
  documents: {
    list(personId) {
      return Client.get(`${resource}/${personId}/documents`)
    },
    upload(personId, formData) {
      return Client.post(`${resource}/${personId}/documents`, formData)
    },
    update(documentId, payload) {
      return Client.patch(`${documentResource}/${documentId}`, payload)
    },
    delete(documentId) {
      return Client.delete(`${documentResource}/${documentId}`)
    },
    download(documentId) {
      return Client.get(`${documentResource}/${documentId}/download`, {
        responseType: "blob",
      })
    },
  },
}
