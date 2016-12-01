# SimpleAsteriskFax
Simple Asterisk Fax manager, written in PHP

This simple PHP script, in conjuction with appriopriate Asterisk dialplan, let users receive FAX in PDF format directly in its mailboxes.

# Usage

Just for example, for Asterisk configured correctly with res_fax and res_fax_spandsp, you can use this dialplan sketch:

```
; ======================================================================================
; FAX
; ======================================================================================

[fax-services]
exten => s,1,NoOp("from-voip: FAX ${CALLERID(num)} ${DID}")
exten => s,n,Set(FAXOPT(ecm)=yes)
exten => s,n,Set(FAXOPT(maxrate)=14400)
exten => s,n,Set(FAXOPT(minrate)=2400)
exten => s,n,Set(FILENAME=fax-${DID}-${STRFTIME(${EPOCH},,%Y%m%d-%H%M%S)})
exten => s,n,Set(FAXFILE=${FILENAME}.tif)
exten => s,n,Goto(fax-${DID},s,1)
exten => i,1,Playback(ss-noservice)
exten => i,n,Hangup

[fax-999] ; FAX machine 999
exten => s,1,NoOp(** Receiving Fax : ${FAXFILE} **)
exten => s,n,Set(FAXOPT(localstationid)=[your station ID])
exten => s,n,Set(DEST=[your email])
exten => s,n,ReceiveFAX(/tmp/${FAXFILE})
exten => s,n,Hangup()
exten => h,1,NoOp(FaxStatus : ${FAXSTATUS})
exten => h,n,GotoIf(${FAXSTATUS}="SUCCESS"?succ:fail) 
exten => h,n(succ),System(echo | [script path]/fax_receive_cb.php /tmp/${FAXFILE} ${FILENAME} '${REMOTESTATIONID}' ${FAXPAGES} ${CALLERID(num)} ${DID} ${DEST})
exten => h,n(fail),Hangup()

[default]
(...)
; FAXes
exten => _FA[X]_.,1,Noop("from-voip: FAX ${CALLERID(num)} ${EXTEN}")
exten => _FA[X]_.,n,Set(DID=${EXTEN:4})
exten => _FA[X]_.,n,Goto(fax-services,s,1)
```

Call '999' to send a fax to this machine.
