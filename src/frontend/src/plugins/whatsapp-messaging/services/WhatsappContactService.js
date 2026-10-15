import WhatsappContactRepository, {
  resource,
} from "../repositories/WhatsappContactRepository.js"

import { Paginator } from "@/Helpers/Paginator.js"

export class WhatsappContactService {
  contactsPaginator(status) {
    return new Paginator(status ? `${resource}?status=${status}` : resource)
  }

  consentLogsPaginator(contactId) {
    return new Paginator(`${resource}/${contactId}/consent-logs`)
  }

  async optIn(personId, phone, consentNote) {
    const { data } = await WhatsappContactRepository.optIn(personId, {
      phone: phone || null,
      consent_note: consentNote,
    })
    return data.data
  }

  async optOut(personId, consentNote) {
    const { data } = await WhatsappContactRepository.optOut(personId, {
      consent_note: consentNote || null,
    })
    return data.data
  }
}
