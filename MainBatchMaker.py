# -*- coding: utf-8 -*-
# This script prepares a batch file that will create the caption files
# and tagged files.
# Originally prepared by Arthur Wendorf in Fall 2012
# Last updated January 15, 2013

# This is the namve of the batch file to be created.
NewBatchFile = open("MainProcessor", 'w')

# This is the name of the tab-delimited text file that contains the following columns:
# Interview ID, Location of Transcripts relative to MainBatchMaker.py, YouTube ID
SourceFile = open("MainInput.txt", 'r')

# Here we are pulling the data from the SourceFile and putting it into an array.
SourceBody = SourceFile.read()
SourceLines = SourceBody.splitlines()
SourceData = []

for line in SourceLines:
    SourceData.append(line.split('\t'))

# The first step in the batch file will be to prepare all of the transcripts for processing by TreeTagger
# using the indicated php file.
NewBatchFile.write("php -f TranscriptPreparerLocal.php\n")

# Here we enter the TreeTagger directory and run it in both English and Spanish.  Then we exit the
# TreeTagger directory.
NewBatchFile.write("cd TreeTagger/\n")
NewBatchFile.write("Cat ../Processing/Prepared.txt |cmd/tree-tagger-english>../Processing/File2.txt\n")
NewBatchFile.write("Cat ../Processing/Prepared.txt |cmd/tree-tagger-spanish-utf8>../Processing/File3.txt\n")
tracker = 0
NewBatchFile.write("cd ..\n")

# We go through each video that we will be working with and submit its transcript to YouTube.
for itemA in SourceData:
    if (tracker != 0):
        NewBatchFile.write('python TranscriptToSrt.py --video-id='+itemA[2]+' --transcript-id='+itemA[1]+'\n')
        NewBatchFile.write('sleep 30\n')
    tracker += 1
tracker = 0

# We go through each video and download its caption file in srt format.
for itemB in SourceData:
    if (tracker != 0):
        NewBatchFile.write('python SrtGatherer.py --video-id='+itemB[2]+'\n')
        NewBatchFile.write('sleep 30\n')
    tracker += 1

# We finally combine the data from the TreeTagger results, the srt file, and the custom tagging script.
NewBatchFile.write('php -f DataCombinerLocal.php\n')
NewBatchFile.close()
