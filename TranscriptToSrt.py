#!/usr/bin/python
# -*- coding: utf-8 -*-

"""This script uploads your transcript to YouTube.
Based on the script created by jeffy@google.com (Jeffrey Posnick) for the
YouTube API demo.
Last updated on May 2, 2013"""

__author__ = "ahwendorf2@gmail.com (Arthur Wendorf)"

import httplib2
import json
import optparse
import os
import sys
import codecs
import time

from apiclient.discovery import build
from oauth2client.file import Storage
from oauth2client.client import OAuth2WebServerFlow
from oauth2client.tools import run
from pysrt import SubRipFile

class Error(Exception):
  """Custom Exception subclass."""
  pass

class CaptionsDemo(object):
  """A class to interact with the YouTube Captions API."""

  # CONSTANTS
  # The client id, secret, and developer key are copied from
  # the Google APIs Console <http://code.google.com/apis/console>
  CLIENT_ID = "342253789856.apps.googleusercontent.com"
  CLIENT_SECRET = "Tm6EEUYebcTDTX5VXgshcIfd"
  
  # Register for a YouTube API developer key at
  # <http://code.google.com/apis/youtube/dashboard/>
  YOUTUBE_DEVELOPER_KEY = ("AI39si49l1wStP2FYHyDR5G0oKRUlyHVr6nIG490nBSAP9cZ8_9STDvA0YH1J1GnrJ8MuYuNHuFKlHq9nmt7Ri4lbvz2il8BWA")

  # Hardcoded YouTube API constants.
  OAUTH_SCOPE = "https://gdata.youtube.com"
  CAPTIONS_URL_FORMAT = ("http://gdata.youtube.com/feeds/api/videos/%s/"
                         "captions?alt=json")
  CAPTIONS_CONTENT_TYPE = "application/vnd.youtube.timedtext; charset=UTF-8"
  CAPTIONS_TITLE = "SPinTX"
  CAPTIONS_LANGUAGE_CODE = "es"################language option###############

  def __init__(self, video_id):
    """Inits CaptionsDemo with the command line arguments."""
    self.video_id = video_id

  def Authenticate(self):
    """Handles OAuth2 authentication.

    The YouTube API requires authenticated access to retrieve the ASR captions
    track and to upload the new translated track.
    We rely on the OAuth2 support in the Google API Client library.
    """
    # Use a file in the user's home directory as the credential cache.
    storage = Storage("%s/%s-oauth" % (os.path.expanduser("~"), sys.argv[0]))
    self.credentials = storage.get()
    if self.credentials is None or self.credentials.invalid:
      # If there are no valid cached credentials, take the user through the
      # OAuth2 login flow, and rely on the client library to cache the
      # credentials once that's complete.
      flow = OAuth2WebServerFlow(
        client_id=self.CLIENT_ID,
        client_secret=self.CLIENT_SECRET,
        scope=self.OAUTH_SCOPE,
        user_agent=sys.argv[0])
      self.credentials = run(flow, storage)
    if self.credentials.invalid:
      time.sleep(30)
      self.credentials = run(flow, storage)

  def SetupHttpRequestObject(self):
    """Creates an httplib2 client and request headers for later use.
    There are certain request headers that all YouTube API requests need to
    include, so we set them up once here.
    The Google API Client library takes care of associating the OAuth2
    credentials with a httplib2.Http object.
    """
    self.headers = {
      "GData-Version": "2",
      "X-GData-Key": "key=%s" % self.YOUTUBE_DEVELOPER_KEY
    }
    self.http = self.credentials.authorize(httplib2.Http())

  def UploadTranscript(self):
    """Uploads transcripts to YouTube."""
    self.headers["Content-Type"] = self.CAPTIONS_CONTENT_TYPE
    self.headers["Content-Language"] = self.CAPTIONS_LANGUAGE_CODE
    self.headers["Slug"] = self.CAPTIONS_TITLE
    url = self.CAPTIONS_URL_FORMAT % self.video_id
    self.translated_captions_body = trans
    response_headers, body = self.http.request(url,
                                               "POST",
                                               body=self.translated_captions_body,
                                               headers=self.headers)

    if response_headers["status"] != "201":
      raise Error("Received HTTP response %s when uploading captions to %s." %
                  (response_headers["status"], url))

  def main(self):
    """Handles the entire program execution."""
    try:
      self.Authenticate()
      self.SetupHttpRequestObject()
      self.UploadTranscript()
      print "Working on "+self.video_id+'\n'
    except Error, e:
      print "The transcript was not successfully submitted.\n"
      print e
    else:
      print "The transcript was successfully submitted.\n"

if __name__ == "__main__":
  opt_parser = optparse.OptionParser()
  opt_parser.add_option("--video-id", help="A YouTube video id in your account.")
  opt_parser.add_option("--transcript-id", help="The route to the transcript from output.")
  options, arguments = opt_parser.parse_args()

  captions_demo = CaptionsDemo(video_id=options.video_id)
  f = open(options.transcript_id, 'r')
  trans = f.read()
  f.close()

  captions_demo.main()
