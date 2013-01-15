#!/usr/bin/python
# -*- coding: utf-8 -*-

"""Used to download all srt files listed in YouTubeIds.txt.
Based on script originally created by jeffy@google.com (Jeffrey Posnick)
Last updated on January 15, 2013"""

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
  CLIENT_ID = "############.apps.googleusercontent.com"
  CLIENT_SECRET = "************************"
  
  # Register for a YouTube API developer key at
  # <http://code.google.com/apis/youtube/dashboard/>
  YOUTUBE_DEVELOPER_KEY = ("**************************************************************************************************")

  # Hardcoded YouTube API constants.
  OAUTH_SCOPE = "https://gdata.youtube.com"
  CAPTIONS_URL_FORMAT = ("http://gdata.youtube.com/feeds/api/videos/%s/"
                         "captions?alt=json")
  CAPTIONS_CONTENT_TYPE = "application/vnd.youtube.timedtext; charset=UTF-8"
  CAPTIONS_TITLE = "******"
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

  def GetAsrTrackUrl(self):
    """Retrieves URL for the ASR track.

    The YouTube API has a REST-ful interface for retrieving a list of captions
    tracks for a given video. We request that list in a JSON response, and then
    loop through the captions tracks until we find the ASR. We save the unique
    URL for the track.

    Raises:
      Error: The ASR caption track info could not be retrieved.
    """
    url = self.CAPTIONS_URL_FORMAT % self.video_id
    response_headers, body = self.http.request(url, "GET", headers=self.headers)

    if response_headers["status"] == "200":
      json_response = json.loads(body)
      realName = str(json_response["feed"]["title"])[29:-2]
      for entry in json_response["feed"]["entry"]:
        if (entry["title"]["$t"] == "SPinTX" and
            entry["content"]["xml$lang"] == self.CAPTIONS_LANGUAGE_CODE):
          self.track_url = entry["content"]["src"]
          return realName
    else:
      raise Error("Received HTTP response %s when requesting %s." %
                  (response_headers["status"], url))

    if self.track_url is None:
      raise Error("Could not find the ASR captions track for this video.")

  def GetSrtCaptions(self, realName):
    """Retrieves and parses the actual ASR captions track's data.

    Given the URL of an ASR captions track, this retrieves it in the SRT format
    and uses the pysrt library to parse it into a format we can manipulate.

    Raises:
      Error: The ASR caption track could not be retrieved.
    """
    response_headers, body = self.http.request("%s?fmt=srt" % self.track_url,
                                               "GET", headers=self.headers)

    if response_headers["status"] == "200":
      realName = realName.replace(' ', '_')
      print 'Working on '+realName+'\n'
      f = open('Processing/SRT/'+realName+'.srt', 'w')
      f.seek(0)
      f.write(body)
      f.close()
    else:
      raise Error("Received HTTP response %s when requesting %s?fmt=srt." %
                  (response_headers["status"], self.track_url))

  def main(self):
    """Handles the entire program execution."""
    try:
      self.Authenticate()
      time.sleep(10)
      self.SetupHttpRequestObject()
      realName = self.GetAsrTrackUrl()
      self.GetSrtCaptions(realName)
    except Error, e:
      print "The srt file was not successfully downloaded."
      print e
    else:
      print "The srt file was successfully downloaded."

if __name__ == "__main__":
  opt_parser = optparse.OptionParser()
  opt_parser.add_option("--video-id", help="A YouTube video id in your account.")
  options, arguments = opt_parser.parse_args()

  if options.video_id is not None:
    captions_demo = CaptionsDemo(video_id=options.video_id)
    captions_demo.main()
  else:
    opt_parser.print_help()
    sys.exit(1)
